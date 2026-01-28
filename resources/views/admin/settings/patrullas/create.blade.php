@extends('adminlte::page')

@section('title', 'Crear Patrulla')

@section('content_header')
    <h1>Creación de una Nueva Patrulla</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('patrullas.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Número económico -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_economico">Número Económico</label>
                                    <input
                                        type="text"
                                        name="numero_economico"
                                        id="numero_economico"
                                        class="form-control @error('numero_economico') is-invalid @enderror"
                                        value="{{ old('numero_economico') }}"
                                        placeholder="Ej. 3190"
                                        required
                                    >
                                    @error('numero_economico')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Unidad -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad</label>
                                    <select
                                        name="unidad_id"
                                        id="unidad_id"
                                        class="form-control @error('unidad_id') is-invalid @enderror"
                                        required
                                    >
                                        <option value="" disabled selected>Seleccione una unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}"
                                                {{ old('unidad_id') == $u->id ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Turno -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="turno_id">Turno</label>
                                    <select
                                        name="turno_id"
                                        id="turno_id"
                                        class="form-control @error('turno_id') is-invalid @enderror"
                                    >
                                        <option value="" selected>Sin turno</option>
                                        @foreach ($turnos as $t)
                                            <option value="{{ $t->id }}"
                                                {{ old('turno_id') == $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('turno_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">
                                        Opcional. Útil si la patrulla está fija a un turno.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Activa -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="activa">Estado</label>
                                    <select
                                        name="activa"
                                        id="activa"
                                        class="form-control @error('activa') is-invalid @enderror"
                                        required
                                    >
                                        <option value="1" {{ old('activa', '1') == '1' ? 'selected' : '' }}>
                                            Activa
                                        </option>
                                        <option value="0" {{ old('activa') == '0' ? 'selected' : '' }}>
                                            Inactiva
                                        </option>
                                    </select>
                                    @error('activa')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('patrullas.index') }}" class="btn btn-secondary">
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
        .form-group label {
            font-weight: bold;
        }
    </style>
@stop

@section('js')
    @if ($errors->any())
        <script>
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
        </script>
    @endif
@stop
