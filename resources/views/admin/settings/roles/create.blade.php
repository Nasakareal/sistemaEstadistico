@extends('adminlte::page')

@section('title', 'Crear Rol')

@section('content_header')
    <h1>Creación de un Nuevo Rol</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf
                        <div class="row justify-content-center">

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">Nombre del Rol</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Ingrese el nombre del rol" value="{{ old('name') }}" required>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad</label>
                                    <select name="unidad_id" id="unidad_id" class="form-control">
                                        @if ($puedeCrearRolGlobal ?? false)
                                            <option value="">-- GLOBAL (todas las unidades) --</option>
                                        @endif
                                        @foreach ($unidades as $unidad)
                                            <option value="{{ $unidad->id }}" {{ old('unidad_id', $unidadIdDefault ?? null) == $unidad->id ? 'selected' : '' }}>
                                                {{ $unidad->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @unless ($puedeCrearRolGlobal ?? false)
                                        <small class="text-muted">Los administradores de unidad solo pueden crear roles para su propia unidad.</small>
                                    @endunless
                                </div>
                            </div>

                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
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
    <script>
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
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
