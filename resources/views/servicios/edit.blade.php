@extends('adminlte::page')

@section('title', 'Editar Servicio')

@section('content_header')
    <h1>Editar Servicio para la Grúa: {{ $grua->nombre }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Servicio</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('servicios.update', [$grua->id, $servicio->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') <!-- Para actualizar usamos PUT -->

                        <div class="row">
                            <!-- Tipo de Vehículo -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_vehiculo">Tipo de Vehículo</label>
                                    <select name="tipo_vehiculo" id="tipo_vehiculo" class="form-control @error('tipo_vehiculo') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione un tipo de vehículo</option>
                                        <option value="camión" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'camión' ? 'selected' : '' }}>Camión</option>
                                        <option value="camioneta" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'camioneta' ? 'selected' : '' }}>Camioneta</option>
                                        <option value="compacto" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'compacto' ? 'selected' : '' }}>Compacto</option>
                                        <option value="motocicleta" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'motocicleta' ? 'selected' : '' }}>Motocicleta</option>
                                        <option value="bicicleta" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'bicicleta' ? 'selected' : '' }}>Bicicleta</option>
                                        <option value="maquinaria" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'maquinaria' ? 'selected' : '' }}>Maquinaria</option>
                                        <option value="otros" {{ old('tipo_vehiculo', $servicio->tipo_vehiculo) == 'otros' ? 'selected' : '' }}>Otros</option>
                                    </select>
                                    @error('tipo_vehiculo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Aseguradora -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="aseguradora">Aseguradora</label>
                                    <input type="text" name="aseguradora" id="aseguradora" class="form-control @error('aseguradora') is-invalid @enderror" 
                                           value="{{ old('aseguradora', $servicio->aseguradora) }}" placeholder="Ingrese el nombre de la aseguradora (opcional)">
                                    @error('aseguradora')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Foto del Vehículo -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="foto_vehiculo">Foto del Vehículo</label>
                                    <input type="file" name="foto_vehiculo" id="foto_vehiculo" class="form-control-file @error('foto_vehiculo') is-invalid @enderror">
                                    @error('foto_vehiculo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <!-- Mostrar la imagen actual si existe -->
                                    @if ($servicio->foto_vehiculo)
                                        <p class="mt-2">Imagen actual:</p>
                                        <img src="{{ asset('storage/' . $servicio->foto_vehiculo) }}" alt="Foto del Vehículo" style="max-width: 200px;" class="img-thumbnail">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Descripción -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="descripcion">Descripción del Servicio</label>
                                    <textarea name="descripcion" id="descripcion" rows="4" class="form-control @error('descripcion') is-invalid @enderror" 
                                              placeholder="Ingrese una descripción del servicio">{{ old('descripcion', $servicio->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('servicios.index', $grua->id) }}" class="btn btn-secondary">
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
        .img-thumbnail {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 4px;
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
