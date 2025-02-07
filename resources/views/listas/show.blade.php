@extends('adminlte::page')

@section('title', 'Detalles del Pase de Lista')

@section('content_header')
    <h1>Detalles del Pase de Lista</h1>
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
                        <!-- Área -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="area">Área</label>
                                <p class="form-control-static">{{ $lista->area }}</p>
                            </div>
                        </div>

                        <!-- Total de Asistentes -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="total_asistentes">Total de Asistentes</label>
                                <p class="form-control-static">{{ $lista->total_asistentes ?? 'No registrado' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Observaciones -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <p class="form-control-static">{{ $lista->observaciones ?? 'Sin observaciones' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fecha de Registro -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_creacion">Fecha de Registro</label>
                                <p class="form-control-static">{{ $lista->created_at->format('d-m-Y H:i:s') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fotos -->
                        <div class="col-md-6">
                            @if ($lista->foto_1)
                                <div class="form-group">
                                    <label for="foto_1">Foto 1</label>
                                    <p><a href="{{ asset('storage/' . $lista->foto_1) }}" target="_blank">Ver foto</a></p>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            @if ($lista->foto_2)
                                <div class="form-group">
                                    <label for="foto_2">Foto 2</label>
                                    <p><a href="{{ asset('storage/' . $lista->foto_2) }}" target="_blank">Ver foto</a></p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <!-- Botón de regreso -->
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('listas.index') }}" class="btn btn-secondary">
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
    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles del pase de lista cargada correctamente."); </script>
@stop
