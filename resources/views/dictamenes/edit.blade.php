@extends('adminlte::page')

@section('title', 'Editar Dictamen')

@section('content_header')
    <h1>Editar Dictamen</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Dictamen</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('dictamenes.update', $dictamen->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') <!-- Para actualizar usamos PUT -->

                        <div class="row">
                            <!-- Número de Dictamen -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_dictamen">Número de Dictamen</label>
                                    <input type="text" name="numero_dictamen" id="numero_dictamen" class="form-control @error('numero_dictamen') is-invalid @enderror"
                                           value="{{ old('numero_dictamen', $dictamen->numero_dictamen) }}"
                                           placeholder="Ingrese el número de dictamen" required>
                                    @error('numero_dictamen')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Año -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="anio">Año</label>
                                    <input type="number" name="anio" id="anio" class="form-control @error('anio') is-invalid @enderror"
                                           value="{{ old('anio', $dictamen->anio) }}"
                                           placeholder="Ingrese el año" min="1900" max="{{ now()->year }}" required>
                                    @error('anio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Nombre del Policía -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre_policia">Nombre del Policía</label>
                                    <input type="text" name="nombre_policia" id="nombre_policia" class="form-control @error('nombre_policia') is-invalid @enderror"
                                           value="{{ old('nombre_policia', $dictamen->nombre_policia) }}"
                                           placeholder="Ingrese el nombre del policía" required>
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
                                           value="{{ old('nombre_mp', $dictamen->nombre_mp) }}"
                                           placeholder="Ingrese el nombre del Ministerio Público" required>
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
                                    <select name="area" id="area" class="form-control @error('area') is-invalid @enderror" required>
                                        <option value="">Seleccione un área</option>
                                        <option value="Siniestros" {{ old('area', $dictamen->area) == 'Siniestros' ? 'selected' : '' }}>Siniestros</option>
                                        <option value="Delegaciones" {{ old('area', $dictamen->area) == 'Delegaciones' ? 'selected' : '' }}>Delegaciones</option>
                                        <option value="Investigación" {{ old('area', $dictamen->area) == 'Investigación' ? 'selected' : '' }}>Investigación</option>
                                    </select>
                                    @error('area')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección para subir el escaneado del dictamen -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="archivo_dictamen">Escaneado del Dictamen (PDF)</label>
                                    <input type="file" name="archivo_dictamen" id="archivo_dictamen" class="form-control-file @error('archivo_dictamen') is-invalid @enderror">
                                    @error('archivo_dictamen')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @if($dictamen->archivo_dictamen)
                                        <p class="mt-2">Archivo actual: <a href="{{ asset('storage/' . $dictamen->archivo_dictamen) }}" target="_blank">Ver Escaneado</a></p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('dictamenes.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
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
        .form-group label {
            font-weight: bold;
        }
    </style>
@stop

@section('js')
    <script>
        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        @endif

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
