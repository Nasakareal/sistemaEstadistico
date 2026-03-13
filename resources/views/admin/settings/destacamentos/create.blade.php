@extends('adminlte::page')

@section('title', 'Crear Destacamento')

@section('content_header')
    <h1>Creación de un Nuevo Destacamento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('destacamentos.store') }}" method="POST">
                        @csrf

                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="clave">Clave</label>
                                    <input type="text"
                                           name="clave"
                                           id="clave"
                                           class="form-control @error('clave') is-invalid @enderror"
                                           value="{{ old('clave') }}"
                                           placeholder="Ej. 01">

                                    @error('clave')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Destacamento</label>
                                    <input type="text"
                                           name="nombre"
                                           id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}"
                                           placeholder="Ej. MORELIA"
                                           required>

                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="municipio">Municipio</label>
                                    <input type="text"
                                           name="municipio"
                                           id="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio') }}"
                                           placeholder="Ej. MORELIA">

                                    @error('municipio')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="activa">Estado</label>
                                    @php $activa = old('activa', 1); @endphp
                                    <select name="activa"
                                            id="activa"
                                            class="form-control @error('activa') is-invalid @enderror">
                                        <option value="1" {{ (int)$activa === 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ (int)$activa === 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>

                                    @error('activa')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary">
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
        .form-group label { font-weight: bold; }
    </style>
@stop

@section('js')
<script>
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
