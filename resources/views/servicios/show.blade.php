@extends('adminlte::page')

@section('title', 'Detalles del Servicio')

@section('content_header')
    <h1>Detalles del Servicio para la Grúa: {{ $grua->nombre }}</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Tipo de Vehículo y Aseguradora -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_vehiculo">Tipo de Vehículo</label>
                                <p class="form-control-static">{{ ucfirst($servicio->tipo_vehiculo) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aseguradora">Aseguradora</label>
                                <p class="form-control-static">{{ $servicio->aseguradora ?? 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Descripción -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion">Descripción del Servicio</label>
                                <p class="form-control-static">{{ $servicio->descripcion ?? 'Sin descripción' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Foto del Vehículo -->
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <label for="foto_vehiculo">Foto del Vehículo</label>
                                @if ($servicio->foto_vehiculo)
                                    <img src="{{ asset('storage/' . $servicio->foto_vehiculo) }}" alt="Foto del Vehículo" class="img-thumbnail" style="max-width: 300px;">
                                @else
                                    <p class="form-control-static">No se proporcionó foto del vehículo.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fecha de Registro -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_registro">Fecha de Registro</label>
                                <p class="form-control-static">{{ $servicio->created_at->format('d-m-Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <!-- Botón de regreso -->
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('servicios.index', $grua->id) }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
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
        .form-control-static {
            display: block;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .img-thumbnail {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 4px;
        }
    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles del servicio cargada correctamente."); </script>
@stop
