@extends('adminlte::page')

@section('title', 'Registrar Dictamen')

@section('content_header')
    <h1>Registro de un Nuevo Dictamen</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <!-- Atributo enctype agregado para permitir la subida de archivos -->
                    <form action="{{ route('dictamenes.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Número de Dictamen -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_dictamen">Número de Dictamen</label>
                                    <input type="text" name="numero_dictamen" id="numero_dictamen" class="form-control"
                                           value="{{ $numeroSiguiente }}" readonly>
                                </div>
                            </div>

                            <!-- Año -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="anio">Año</label>
                                    <input type="text" name="anio" id="anio" class="form-control"
                                           value="{{ now()->year }}" readonly>
                                </div>
                            </div>

                            <!-- Nombre del Policía -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre_policia">Nombre del Policía</label>
                                    <input type="text" name="nombre_policia" id="nombre_policia" class="form-control @error('nombre_policia') is-invalid @enderror"
                                           value="{{ old('nombre_policia') }}" placeholder="Ingrese el nombre del policía" required>
                                    @error('nombre_policia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Nombre del MP -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre_mp">Nombre del MP</label>
                                    <input type="text" name="nombre_mp" id="nombre_mp" class="form-control @error('nombre_mp') is-invalid @enderror"
                                           value="{{ old('nombre_mp') }}" placeholder="Ingrese el nombre del Ministerio Público" required>
                                    @error('nombre_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Área -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">Área</label>

                                    @if ($rolUsuario === 'Administrador')
                                        <!-- Selección editable para el Administrador -->
                                        <select name="area" id="area" class="form-control @error('area') is-invalid @enderror" required>
                                            <option value="">Seleccione un área</option>
                                            @foreach ($areasDisponibles as $area)
                                                <option value="{{ $area }}" {{ old('area') == $area ? 'selected' : '' }}>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <!-- Área asignada al usuario común (bloqueada) -->
                                        <input type="text" name="area" id="area" class="form-control" value="{{ $areaUsuario }}" readonly>
                                    @endif

                                    @error('area')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Archivo del Dictamen -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="archivo_dictamen">Archivo del Dictamen (PDF)</label>
                                    <input type="file" name="archivo_dictamen" id="archivo_dictamen" class="form-control @error('archivo_dictamen') is-invalid @enderror">
                                    @error('archivo_dictamen')
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
                                    <a href="{{ route('dictamenes.index') }}" class="btn btn-secondary">
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
