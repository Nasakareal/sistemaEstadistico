@extends('adminlte::page')

@section('title', 'Detalles de la Liberación')

@section('content_header')
    <h1>Detalles de Liberación del Vehículo</h1>
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
                        {{-- Vehículo --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vehiculo">Vehículo</label>
                                <p class="form-control-static">{{ $vehiculo->marca }} - {{ $vehiculo->modelo }} - {{ $vehiculo->placas }}</p>
                            </div>
                        </div>

                        {{-- Fecha de liberación --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_liberacion">Fecha de Liberación</label>
                                <p class="form-control-static">{{ \Carbon\Carbon::parse($liberacion->fecha_liberacion)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Personas autorizadas --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="personas_autorizadas">Personas Autorizadas para Recoger</label>
                                <p class="form-control-static">{{ $liberacion->personas_autorizadas }}</p>
                            </div>
                        </div>

                        {{-- Observaciones --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <p class="form-control-static">{{ $liberacion->observaciones ?? 'Ninguna' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Estado de PDF de grúa --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pdf_gruas">Liberación por Grúas</label>
                                @if($liberacion->pdf_gruas)
                                    <a href="{{ asset('storage/' . $liberacion->pdf_gruas) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        Ver PDF de Grúas
                                    </a>
                                @else
                                    <p class="form-control-static text-muted">Aún no se ha subido liberación por grúas.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Botón para descargar (placeholder) --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="acuse">Acuse</label>
                                <a href="{{ route('liberacion.descargar', $vehiculo->id) }}" class="btn btn-outline-dark btn-sm">
                                    Descargar Acuse PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        {{-- Botón de regreso --}}
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ url()->previous() }}" class="btn btn-secondary">
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
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 4px;
        }
    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles de liberación cargada."); </script>
@stop
