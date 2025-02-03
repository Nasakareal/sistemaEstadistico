@extends('adminlte::page')

@section('title', 'Registrar Servicio')

@section('content_header')
    <h1>Registro de un Nuevo Servicio para la Grúa: {{ $grua->nombre }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('servicios.store', $grua->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <!-- Tipo de Vehículo -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_vehiculo">Tipo de Vehículo</label>
                                    <select name="tipo_vehiculo" id="tipo_vehiculo" class="form-control @error('tipo_vehiculo') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione un tipo de vehículo</option>
                                        <option value="camión" {{ old('tipo_vehiculo') == 'camión' ? 'selected' : '' }}>Camión</option>
                                        <option value="camioneta" {{ old('tipo_vehiculo') == 'camioneta' ? 'selected' : '' }}>Camioneta</option>
                                        <option value="compacto" {{ old('tipo_vehiculo') == 'compacto' ? 'selected' : '' }}>Compacto</option>
                                        <option value="motocicleta" {{ old('tipo_vehiculo') == 'motocicleta' ? 'selected' : '' }}>Motocicleta</option>
                                        <option value="bicicleta" {{ old('tipo_vehiculo') == 'bicicleta' ? 'selected' : '' }}>Bicicleta</option>
                                        <option value="maquinaria" {{ old('tipo_vehiculo') == 'maquinaria' ? 'selected' : '' }}>Maquinaria</option>
                                        <option value="otros" {{ old('tipo_vehiculo') == 'otros' ? 'selected' : '' }}>Otros</option>
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
                                           value="{{ old('aseguradora') }}" placeholder="Ingrese el nombre de la aseguradora (opcional)">
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
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Descripción -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="descripcion">Descripción del Servicio</label>
                                    <textarea name="descripcion" id="descripcion" rows="4" class="form-control @error('descripcion') is-invalid @enderror" 
                                              placeholder="Ingrese una descripción del servicio (opcional)">{{ old('descripcion') }}</textarea>
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
                                    <i class="fa-solid fa-check"></i> Registrar
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
