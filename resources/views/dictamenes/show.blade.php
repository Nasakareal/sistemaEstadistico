@extends('adminlte::page')

@section('title', 'Detalles del Dictamen')

@section('content_header')
    <h1>Detalles del Dictamen</h1>
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
                        <!-- Número de Dictamen y Año -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_dictamen">Número de Dictamen</label>
                                <p class="form-control-static">{{ $dictamen->numero_dictamen }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="anio">Año</label>
                                <p class="form-control-static">{{ $dictamen->anio }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Nombre del Policía y MP -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre_policia">Nombre del Policía</label>
                                <p class="form-control-static">{{ $dictamen->nombre_policia }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre_mp">Nombre del MP</label>
                                <p class="form-control-static">{{ $dictamen->nombre_mp }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Área -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="area">Área</label>
                                <p class="form-control-static">{{ $dictamen->area }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fecha de Creación -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_creacion">Fecha de Registro</label>
                                <p class="form-control-static">{{ $dictamen->created_at->format('d-m-Y') }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($dictamen->archivo_dictamen)
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Archivo del Dictamen</label>
                                    <p><a href="{{ asset('storage/' . $dictamen->archivo_dictamen) }}" target="_blank">Ver archivo</a></p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr>
                    <div class="row">
                        <!-- Botón de regreso -->
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('dictamenes.index') }}" class="btn btn-secondary">
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
    <script> console.log("Vista de detalles del dictamen cargada correctamente."); </script>
@stop
