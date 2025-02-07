@extends('adminlte::page')

@section('title', 'Detalles del Formato')

@section('content_header')
    <h1>Detalles del Formato</h1>
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
                        <!-- Nombre del Formato -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre del Formato</label>
                                <p class="form-control-static">{{ $formato->nombre }}</p>
                            </div>
                        </div>

                        <!-- Descripción del Formato -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <p class="form-control-static">{{ $formato->descripcion }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fecha de Registro -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_creacion">Fecha de Registro</label>
                                <p class="form-control-static">{{ $formato->created_at->format('d-m-Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Archivo del Formato -->
                    @if ($formato->archivo_pdf)
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Archivo del Formato</label>
                                    <p><a href="{{ asset('storage/' . $formato->archivo_pdf) }}" target="_blank">Ver archivo PDF</a></p>
                                </div>
                            </div>
                        </div>
                    @endif


                    <hr>
                    <div class="row">
                        <!-- Botón de regreso -->
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('formatos.index') }}" class="btn btn-secondary">
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
    <script> console.log("Vista de detalles del formato cargada correctamente."); </script>
@stop
