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

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vehículo</label>
                                <p class="form-control-static">
                                    {{ $vehiculo->marca }} - {{ $vehiculo->modelo }} - {{ $vehiculo->placas }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de Liberación</label>
                                <p class="form-control-static">
                                    {{ \Carbon\Carbon::parse($liberacion->fecha_liberacion)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Personas Autorizadas para Recoger</label>
                                <p class="form-control-static">
                                    {{ $liberacion->personas_autorizadas }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <p class="form-control-static">
                                    {{ $liberacion->observaciones ?? 'Ninguna' }}
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Corralón Actual</label>

                                @if($vehiculo->corralon)
                                    <p class="form-control-static text-success">
                                        <i class="fa-solid fa-warehouse"></i>
                                        {{ $vehiculo->corralon }}
                                    </p>
                                @else
                                    <p class="form-control-static text-danger">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        ESTE VEHÍCULO NO TIENE CORRALÓN REGISTRADO
                                    </p>
                                @endif

                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Liberación por Grúas</label>

                                @if($liberacion->pdf_gruas)
                                    <a href="{{ asset('storage/' . $liberacion->pdf_gruas) }}"
                                       target="_blank"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-file-pdf"></i> Ver PDF de Grúas
                                    </a>
                                @else
                                    <p class="form-control-static text-muted">
                                        Aún no se ha subido liberación por grúas.
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Acuse Oficial</label>

                                <a href="{{ route('liberacion.descargar', $vehiculo->id) }}"
                                   class="btn btn-acuse btn-sm">
                                    <i class="fa-solid fa-download"></i>
                                    Descargar Acuse PDF
                                </a>

                            </div>
                        </div>

                    </div>

                    <hr>

                    <div class="row text-center">

                        <div class="col-md-4 mb-2">
                            <a href="{{ route('liberacion.edit', $vehiculo->id) }}"
                               class="btn btn-warning btn-block">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Editar Liberación
                            </a>
                        </div>

                        <div class="col-md-4 mb-2">
                            @if($liberacion->hecho_id)
                                <a href="{{ route('vehiculos.edit', ['hecho' => $liberacion->hecho_id, 'vehiculo' => $vehiculo->id]) }}"
                                   class="btn btn-info btn-block">
                                    <i class="fa-solid fa-car"></i>
                                    Editar Vehículo
                                </a>
                            @else
                                <button type="button" class="btn btn-info btn-block" disabled>
                                    <i class="fa-solid fa-car"></i>
                                    Editar Vehículo
                                </button>
                            @endif
                        </div>

                        <div class="col-md-4 mb-2">
                            <a href="{{ url()->previous() }}"
                               class="btn btn-secondary btn-block">
                                <i class="fa-solid fa-arrow-left"></i>
                                Volver
                            </a>
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

    .btn-acuse {
        background: #c0392b;
        color: white !important;
        font-weight: bold;
        border-radius: 8px;
        padding: 10px 15px;
        transition: 0.2s ease-in-out;
    }

    .btn-acuse:hover {
        background: #922b21;
        transform: scale(1.03);
    }
</style>
@stop

@section('js')
<script>
    console.log("Vista de detalles de liberación cargada.");
</script>
@stop
